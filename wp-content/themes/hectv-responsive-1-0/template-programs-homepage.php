<?php
/*
Template Name: Programs Homepage
*/
?>
<?php get_header(); ?>

	<main role="main" id="programs-homepage" class="endless">

	<div class="page-inner clearfix">

		<div id="primary-series" class="module-wide">

			<h2>Programs</h2>

			<div id="top" class="inner clearfix">

				<?php $pageData = get_post_custom(); ?>

				<?php $loaded   = array(); ?>

				<?php while ( have_rows('series') ) : the_row(); ?>

				<?php $seriesID    = current( get_sub_field('series') ); ?>
				<?php $loaded[]    = $seriesID; ?>
				<?php $series      = get_page( $seriesID ); ?>
				<?php $recent      = get_posts( array( 'post_parent' => $seriesID, 'post_type' => 'lb_playlist', 'order' => 'post_date', 'orderby' => 'DESC', 'posts_per_page' => 1 ) ); ?>
				<?php $thumbnailID = current( get_post_custom_values( 'video_image', $recent[0]->ID ) ); ?>
				<?php $recentImg   = wp_get_attachment_image_src( $thumbnailID, 'media-medium' ); ?>

				<a href="<?php echo get_permalink( $series->ID ); ?>">
					<div class="series" style="background-image:url(<?php echo $recentImg[0]; ?>);">
							<?php if( ( time() - (3600 * 24) ) < strtotime( $recent[0]->post_modified ) ){ ?>
							<span class="updated">NEW</span>
							<?php } ?>
							<div class="inner">
								<h3><?php echo $series->post_title; ?></h3>
							</div>
					</div>
				</a>

				<?php endwhile; ?>

        	</div>
		</div>

			<div id="local-series" class="module-wide clearfix">
				<h2>Local Series</h2>
				<ul class="local-continued">

				<?php $seriesList = get_posts( array( 'post_parent' => 16789, 'post_type' => 'page', 'posts_per_page' => -1, 'meta_key' => 'series_type', 'meta_value' => 'local series', 'orderby' => 'post_name', 'order' => 'ASC' ) ); ?>

				<?php foreach( $seriesList as $seriesItem ){ ?>
					<li style="margin-bottom:30px;">
						<a href="<?php echo get_permalink( $seriesItem->ID ); ?>">
							<h3 class="<?php echo ( in_array( $seriesItem->ID, $loaded ) )?'primary':''; ?>"><?php echo $seriesItem->post_title; ?></h3>
							<p><?php echo $seriesItem->post_content; ?></p>
						</a>
					</li>
				<?php } ?>

			</div>

			<div id="local-specials" class="module-wide clearfix">

				<h2>Local Specials</h2>

				<ul class="local-continued">

				<?php $seriesList = get_posts( array( 'post_parent' => 16789, 'post_type' => 'page', 'exclude' => $loaded, 'posts_per_page' => -1, 'meta_key' => 'series_type', 'meta_value' => 'local special', 'orderby' => 'post_name', 'order' => 'ASC' ) ); ?>

				<?php foreach( $seriesList as $seriesItem ){ ?>


					<li style="margin-bottom:30px;">
						<a href="<?php echo get_permalink( $seriesItem->ID ); ?>">
							<h3 class="<?php echo ( in_array( $seriesItem->ID, $loaded ) )?'primary':''; ?>"><?php echo $seriesItem->post_title; ?></h3>
							<p><?php echo $seriesItem->post_content; ?></p>
						</a>
					</li>

				<?php } ?>

				</ul>

			</div>

            <?php $seriesList = get_posts( array( 'post_parent' => 16789, 'post_type' => 'page', 'exclude' => $loaded, 'posts_per_page' => -1, 'meta_key' => 'series_type', 'meta_value' => 'national series', 'orderby' => 'post_name', 'order' => 'ASC', 'post_status' => 'private' ) ); ?>
			<?php if( count($seriesList) ) { ?>
                <div class="module-wide clearfix">

                    <h2>National Series</h2>

                    <ul class="local-continued">

                    <?php foreach( $seriesList as $seriesItem ){ ?>

                        <li style="margin-bottom:30px;">
                            <h3><?php echo $seriesItem->post_title; ?></h3>
                            <p><?php echo $seriesItem->post_content; ?></p>
                        </li>

                    <?php } ?>

                    </ul>

                </div>
            <?php } ?>

		</div>

	</main>

<?php get_footer(); ?>
