<?php
/*
Template Name: TV Schedule
*/
?>

<?php get_header(); ?>

<?php $requestMonth = strtolower( get_query_var( "calendarmonth" ) ); ?>
<?php $requestYear  = get_query_var( "calendaryear" ); ?>
<?php $pageName     = get_query_var( "pagename" ); ?>

<?php $monthNames   = array( 1 => "january", 2 => "february", 3 => "march", 4 => "april", 5 => "may", 6 => "june",
							 7 => "july", 8 => "august", 9 => "september", 10=> "october", 11 => "november", 12 => "december" ); ?>

<?php if( in_array( $requestMonth, $monthNames ) && is_numeric( $requestYear ) && !empty( $requestYear ) &&
		  !empty( $requestMonth ) && ( strlen( $requestYear ) == 4 ) ){ ?>

	<?php $validQuery = true; ?>

<?php } ?>


<?php if( $validQuery ){ ?>

	<?php $queryMonth     = array_search( $requestMonth, $monthNames ); ?>
	<?php $queryYear      = $requestYear; ?>
	<?php $queryMonthName = ucfirst( $requestMonth ); ?>

	<?php $date           = new DateTime( "$queryYear-$queryMonth-01" ); ?>

<?php }else{ ?>

	<?php $queryTime      = strtotime( current_time( "c", 0 ) ); ?>
	<?php $queryDay       = date( "d", $queryTime ); ?>
	<?php $queryMonth     = date( "n", $queryTime ); ?>
	<?php $queryYear      = date( "Y", $queryTime ); ?>
	<?php $queryMonthName = ucfirst( date( "F", $queryTime ) ); ?>

	<?php $date           = new DateTime( "$queryYear-$queryMonth-01" ); ?>

<?php } ?>

<?php $previousMonth      = new DateTime( "$queryYear-$queryMonth-01" ); ?>
<?php $nextMonth          = new DateTime( "$queryYear-$queryMonth-01" ); ?>


<?php $previousMonth->modify("-1 month"); ?>
<?php $nextMonth->modify("+1 month"); ?>

<?php $totalDaysinMonth   = cal_days_in_month( CAL_GREGORIAN, $queryMonth, $queryYear ); ?>

<?php $startUnixTime      = $date->format("U"); ?>
<?php $endUnixTime        = $nextMonth->format("U"); ?>

<script type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/_/js/tv-schedule.js"></script>

<main class="page clearfix" id="tv-schedule" data-prev="/tv-schedule/<?php echo $previousMonth->format("Y/F/"); ?>" data-next="/tv-schedule/<?php echo $nextMonth->format("Y/F/"); ?>">

    <div class="page-inner">

        <section class="page-left"> <!-- start page left-->

			<?php if (have_posts()): while (have_posts()) : the_post(); ?>

            <div class="page-description module-wide">

                <div class="inner">

                    <h2><?php the_title(); ?></h2>

                    <?php the_content(); ?>

                </div>

            </div>

            <?php endwhile; ?>

            <?php endif; ?>

            <div id="tv-schedule" class="module-wide item-viewer">

                <div class="inner">

                    <div class="calendar clearfix">

                        <div class="month">

                            <button id="event-previous" class="event-nav"></button>

                            <span class="month"><?php echo $queryMonthName; ?> <?php echo $queryYear; ?></span>

                            <button id="event-next" class="event-nav"></button>

                        </div>

                        <ul id="dates" class="clearfix no-select">

	                        <?php $today = current_time( "m-d-Y", 0 ); ?>
	                        <?php $first = $date->format('w'); ?>

							<?php $prev  = new DateTime( "$queryYear-$queryMonth-01" ); ?>
							<?php $prev->modify("- $first day"); ?>

	                        <?php for( $y = 1; $y <= $first; $y++ ){ ?>

	                        	<li class="prev" data-link="/tv-schedule/<?php echo $prev->format('Y/F/'); ?>#prev">
	                        		<span class="month"><?php echo $prev->format('F'); ?></span>
	                                <span class="date"><?php echo $prev->format('jS'); ?></span>
	                                <span class="day"><?php echo $prev->format('l'); ?></span>
	                        	</li>
								<?php $prev->modify("+ 1 day"); ?>

	                        <?php } ?>

	                        <?php for( $x = 1; $x <= $totalDaysinMonth; $x++ ){ ?>

		                        <?php $currentDate = $date->format('m-d-Y'); ?>
		                        <?php $start       = ( $date->format('w') == 0 ) ? 'start' : ''; ?>
		                        <?php $end         = ( $date->format('w') == 6 ) ? 'end' : ''; ?>

		                        <li class="date <?php echo ( $currentDate == $today ) ? 'today':''; ?>" rel="<?php echo $currentDate; ?>" data-link="/events/<?php echo $date->format('Y/F/'); ?>" data-week="<?php echo $date->format('W'); ?>">
			                        <span class="month"><?php echo $prev->format('F'); ?></span>
	                                <span class="date"><?php echo $date->format('jS'); ?></span>
	                                <span class="day"><?php echo $date->format('l'); ?></span>
	                            </li>

	                            <?php $date->modify('+ 1 day'); ?>

	                        <?php } ?>

	                        <?php $last = $date->format('w'); ?>

	                        <?php for( $y = $last; $y < 7; $y++ ){ ?>

	                        	<li class="next" data-link="/tv-schedule/<?php echo $date->format('Y/F/'); ?>#next">
		                        	<span class="month"><?php echo $date->format('F'); ?></span>
	                                <span class="date"><?php echo $date->format('jS'); ?></span>
	                                <span class="day"><?php echo $date->format('l'); ?></span>
	                        	</li>

	                        	<?php $date->modify('+ 1 day'); ?>

	                        <?php } ?>

                        </ul>

                    </div>


                    <div class="events clearfix">

						<?php $tv_schedule = $wpdb->get_results( "SELECT * FROM wp_schedule WHERE time BETWEEN $startUnixTime AND $endUnixTime AND approved = '1'" ); ?>

                        <ul id="tv-listings">

	                        <?php foreach ( $tv_schedule as $tv_item ) { ?>

		                        <?php $tv_item_url = ( get_post_status( $tv_item->episodeID ) == "publish" ) ? get_permalink( $tv_item->episodeID ) : false; ?>

	                            <li class="tv-post clearfix" data-date="<?php echo date( 'm-d-Y', $tv_item->time ); ?>">

	                                <div class="left">

		                                <?php if( $tv_item_url ){ ?>
											<a href="<?php echo $tv_item_url; ?>">
										<?php } ?>

										<?php $video_image  = get_post_custom_values( "video_image", $tv_item->episodeID ); ?>

										<?php $episode_data = get_post_custom( $tv_item->episodeID ); ?>
										<?php $thumb        = wp_get_attachment_image_src( $video_image[0], 'media-medium' ); ?>

										<div class="img-wrap">
                                            
                                        <?php if( $tv_item_url ){ ?>
                                            <a href="<?php echo $tv_item_url; ?>" class="btn">Watch Now</a>
										<?php } ?>
                                            
										<?php if( $thumb ){ ?>

		                                    <img src="<?php echo $thumb[0]; ?>">

	                                    <?php }else{ ?>

											<img src="<?php echo get_template_directory_uri(); ?>/_/graphics/unavailable.png">

	                                    <?php } ?>

										</div>

	                                    <?php if( $tv_item_url ){ ?>
											</a>
										<?php } ?>
	                                </div>

	                                <div class="right">

		                                <div class="info">

											<span class="date"><?php echo date( 'D F jS', $tv_item->time ); ?></span>
		                                    <span class="divider">|</span>
		                                    <span class="time"><?php echo date( 'g:iA', $tv_item->time ); ?></span>

										</div>

		                                <?php if( $tv_item_url ){ ?>
		                                <a href="<?php echo $tv_item_url; ?>">
			                            <?php } ?>

			                            	<?php if( $episode_data['series_title_disable'][0] ){ ?>
											<?php $title  = get_the_title( $tv_item->episodeID ); ?>
											<?php }else{ ?>
											<?php $title  = get_the_title( $tv_item->seriesID ) . ": " . get_the_title( $tv_item->episodeID ); ?>
											<?php } ?>

			                            	<?php $title  = str_replace( "Private: ", "", $title ); ?>

		                                    <h4 class="title"><?php echo $title; ?></h4>
	                                    <?php if( $tv_item_url ){ ?>
		                                </a>
		                                <?php } ?>

	                                    <?php $description = get_post_custom_values( 'meta_description', $tv_item->episodeID ); ?>
	                                    <p><?php echo $description[0]; ?></p>

	                                </div>
	                            </li>

                            <?php } ?>

                            	<li id="no-results">

                            		<h2>Sorry No Scheduled Programs Found.</h2>

                            	</li>

                        </ul>

                    </div> <!-- End Events List -->

                </div> <!-- End Inner -->

            </div>

        </section> <!-- end page left-->

        <section class="page-right"> <!-- start page right-->
        
        <?php require_once('module-tv-schedule.php'); ?>

        <div class="contact-module module dark clearfix">

			<div class="inner">

				<h2>On Air</h2>

				<div class="tv-providers">

					<div class="provider-info">
						<span class="provider">Charter Cable (In St. Louis City &amp; County)</span>
						<span class="channel">Channel 989 (with converter box)<br>
Channel 108.26 or 118.26 (for digital TVs)</span>
					</div>

					<div class="provider-info">
						<span class="provider">AT&amp;T U-Verse</span>
						<span class="divider">|</span>
						<span class="channel">Channel 99</span>
					</div>

					<div class="provider-info">
						<span class="provider">On Air</span>
						<span class="divider">|</span>
						<span class="channel">Channel 2.2</span>
					</div>
				</div>

			</div>
		</div>

            <?php hectv_create_trending(5); ?>

        </section> <!-- end page right-->

    </div> <!--- end inner -->

</main>

<?php get_footer(); ?>
