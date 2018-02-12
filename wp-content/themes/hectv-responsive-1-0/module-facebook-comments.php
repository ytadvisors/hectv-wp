<?php function hectv_load_facebook_comments($module = true){ ?>

	<?php if( $module ){ ?>
	<div id="section-comments" class="trending module-wide clearfix">
	<?php } ?>

		<div class="inner">

			<div class="fb-comments" data-href="<?php the_permalink(); ?>" data-width="100%" data-numposts="2" data-colorscheme="light"></div>

		</div>

	<?php if( $module ){ ?>
	</div>
	<?php } ?>

<?php } ?>