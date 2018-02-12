<?php
/*
Template Name: Magazine Detail
*/
?>

<?php get_header(); ?>

<main class="page clearfix" id="magazine-detail">
	
	<div class="page-inner clearfix">
		
		<section class="page-left">
			<div class="module-wide">
				<div class="inner">
					<h2>April 2015 - HEC-TV Magazine</h2>
					<h3>HEC-TV’s Don Wolff, host of I Love Jazz, wins a national Jazz Hero Award for 2015! Congratulations, Don!</h3>
					
				<iframe src="http://www.pageturnpro.com/Higher-Education-Channel/64964-HEC-TV-Magazine-April-2015/index.html"></iframe>
				</div>
			</div>
			
		</section>
		<section class="page-right">
			<div class="module">
				<div class="inner">
					<h2>More Issues</h2>
					<ul class="issues">
						
						<li><a href="#"><?php wp_get_archives( $args ); ?></a></li>
						<li><a href="#"></a></li>
					</ul>
				</div>
			</div>
		</section>
		
	</div>
	
</main>
<?php get_footer(); ?>
