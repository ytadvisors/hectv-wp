<?php
/*
Template Name: Magazine Landing
*/
?>

<?php get_header(); ?>
	
<main class="page clearfix" id="magazine-landing">
	
	<div class="page-inner clearfix">
		<section class="page-left">
			<div class="module-wide">
	
				<div class="inner">
					<h2>Magazine</h2>
					
<!-- 					<h3>Current Issue</h3> -->
					
					<div class="magazine current clearfix">	
						<div class="img-wrap">
							<a href="#"><img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-magazine-cover-temp.jpg"></a>
						</div>
						<div class="right">
							<span><a href="#">HEC-TV Magazine April 2015</a></span>
							<p>HEC-TV's Don Wolff, host of I Love Jazz, wins a national Jazz Hero Award for 2015! Congratulations, Don!</p>
							<a class="read-issue" href="<?php echo get_permalink(); ?>">Read Issue</a>
						</div>
					</div>	
					
<!-- 					<h3>Previous Issues</h3> -->
					
					<div class="magazine current clearfix">	
						<div class="img-wrap">
							<img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-magazine-cover-temp.jpg">
						</div>
						<div class="right">
							<span>HEC-TV Magazine April 2015</span>
							<p>HEC-TV's Don Wolff, host of I Love Jazz, wins a national Jazz Hero Award for 2015! Congratulations, Don!</p>
							<a class="read-issue" href="#">Read Issue</a>
						</div>
					</div>	
					
				</div>
			</div>
	    	    
		</section>
		<section class="page-right">
			<?php hectv_create_trending( 3 ); ?>
		</section>
	</div>
</main>
<?php get_footer(); ?>
