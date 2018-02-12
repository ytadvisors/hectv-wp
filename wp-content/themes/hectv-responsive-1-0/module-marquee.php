<?php function hectv_marquee_html(){ ?>
<div id="marquee-wrap">
	<div id="marquee" class="clearfix">

		<?php $slides = get_option('lb_marquee_homepage_slides_v2'); ?>

		<?php foreach( $slides as $index => $slide ){ ?>

		<div class="marquee-item" style="background-image: url('<?php echo $slide[0]->imageURL; ?>');" rel="<?php echo $index; ?>">

	    	<div class="wrap">

	        	<div class="marquee-wrap">

					<span class="marquee-title"><?php echo $slide[0]->series; ?></span>

					<div class="content">

		            	<h3><?php echo $slide[0]->headline; ?></h3>
		            	<p><?php echo $slide[0]->excerpt; ?></p>
						
						<?php $cta_action = ( !empty( $slide[0]->cta_override ) ) ? $slide[0]->cta_override : get_permalink( $slide[0]->post ); ?>
						
						<div style="text-align:left">
			            	<a class="btn" href="<?php echo $cta_action; ?>"><?php echo $slide[0]->cta; ?></a>
						</div>

					</div>

	        	</div>
	    	</div>

		</div>

		<?php } ?>

	</div> <!-- End Marquee -->
	
	<ul id="marquee-pager-wrap">
		
		<img class="nav prev" src="<?php echo get_template_directory_uri(); ?>/_/graphics/arrow-left.png">
		
		<ul id="marquee-pager">
		
		<?php foreach( $slides as $index => $slide ){ ?>
			<?php $pager_image = wp_get_attachment_image_src( $slide[0]->photo, 'medium' ); ?>
			<li class="" rel="<?php echo $index; ?>">
				<img style="height:100px;" src="<?php echo $pager_image[0]; ?>">
				<h4><?php echo $slide[0]->headline; ?></h4>
			</li>
		
		<?php } ?>
		
		</ul>
		
		<img class="nav next" src="<?php echo get_template_directory_uri(); ?>/_/graphics/arrow-right.png">
		
	</ul>

</div>

<?php } ?>