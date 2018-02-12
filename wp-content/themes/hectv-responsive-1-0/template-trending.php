<?php

	/*
		Template Name: Trending
	*/
?>
<?php get_header(); ?>

 <main class="page clearfix" id="blog">

	<div class="page-inner clearfix">

    	<section class="page-left">

			<?php $trendingVideos = get_posts( array( "post_status" => "publish", "post_type" => array( "lb_playlist", "lb_video" ), "orderby" => "meta_value_num, date", "order" => "DESC", "meta_key" => "socialcount_TOTAL", "posts_per_page" => 30 ) ); ?>

			<?php foreach( $trendingVideos as $trendingVideo ){ ?>

			<?php if( $trendingVideo->post_type == "lb_playlist" ){ ?>

				<?php $videoPosts[] = hectv_create_media_slat_by_post($trendingVideo); ?>

			<?php } ?>

			<?php }; ?>

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

    	</section>

    	<section class="page-right">

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