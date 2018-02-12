<?php function hectv_create_partners_list( $display = 7 ){ ?>

<?php if( have_rows('public_school_partners', 10614) ): ?>
<?php while ( have_rows('public_school_partners', 10614) ) : the_row(); ?>

<?php $partners_full[] = get_sub_field('partner'); ?>

<?php endwhile; ?>
<?php endif; ?>

<?php if( have_rows('higher_education_partners', 10614) ): ?>
<?php while ( have_rows('higher_education_partners', 10614) ) : the_row(); ?>

<?php $partners_full[] = get_sub_field('partner'); ?></li>

<?php endwhile; ?>
<?php endif; ?>

<?php print_r($partner); ?>

<div id="partners" class="module">

	 <div class="inner">

		 <h2>Partners</h2>
		 <ul>
			 <?php $partners = array_rand( $partners_full, 9 ); ?>
			 <?php foreach( $partners as $index ){ ?>
			 <li><?php echo $partners_full[$index]; ?></li>
			 <?php } ?>
		 </ul>

		 <a href="<?php echo site_url('about-us'); ?>" class="btn">View All</a>

	 </div>

</div>
<?php } ?>