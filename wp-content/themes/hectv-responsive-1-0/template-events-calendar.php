<?php
/*
Template Name: Events Calendar
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

<script type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/_/js/events.js"></script>

<main class="page clearfix" id="events-calendar" data-prev="/events/<?php echo $previousMonth->format("Y/F/"); ?>" data-next="/events/<?php echo $nextMonth->format("Y/F/"); ?>">

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

            <div id="events" class="module-wide item-viewer">

                <div class="inner">

                    <div class="calendar clearfix">

                        <div class="month">

                            <button id="event-previous" class="event-nav"></button>

                            <span class="month"><?php echo $queryMonthName; ?> <?php echo $queryYear; ?></span>

                            <button id="event-next" class="event-nav"></button>

                        </div>

                        <ul id="dates" class="clearfix noselect">

	                        <?php $today = current_time( "m-d-Y", 0 ); ?>
	                        <?php $first = $date->format('w'); ?>

							<?php $prev  = new DateTime( "$queryYear-$queryMonth-01" ); ?>
							<?php $prev->modify("- $first day"); ?>

	                        <?php for( $y = 1; $y <= $first; $y++ ){ ?>

	                        	<li class="prev" data-link="/events/<?php echo $prev->format('Y/F/'); ?>#prev">
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
	                                <span class="date"><?php echo $date->format('jS'); ?></span>
	                                <span class="day"><?php echo $date->format('l'); ?></span>
	                            </li>

	                            <?php $date->modify('+ 1 day'); ?>

	                        <?php } ?>

	                        <?php $last = $date->format('w'); ?>

	                        <?php for( $y = $last; $y < 7; $y++ ){ ?>

	                        	<li class="next" data-link="/events/<?php echo $date->format('Y/F/'); ?>#next">
	                                <span class="date"><?php echo $date->format('jS'); ?></span>
	                                <span class="day"><?php echo $date->format('l'); ?></span>
	                        	</li>

	                        	<?php $date->modify('+ 1 day'); ?>

	                        <?php } ?>

                        </ul>

                    </div>


                    <div class="events clearfix">

	                    <?php $eventPostsQueryParameters = array( 'posts_per_page' => -1,
																  'orderby'        => 'start_time',
																  'order'          => 'ASC',
																  'post_type'      => 'event',
																  'meta_query'     => array(
																			array(
																				'key'     => 'start_time',
																				'value'   => array( $startUnixTime, $endUnixTime ),
																				'type'    => 'numeric',
																				'compare' => 'BETWEEN',
																			)
																	)
																); ?>

						<?php $eventPosts = get_posts( $eventPostsQueryParameters ); ?>

                        <ul id="events-listing">

	                        <?php foreach ( $eventPosts as $event ) : setup_postdata( $event ); ?>

	                        <?php $eventData = get_post_custom( $event->ID ); ?>

	                            <li class="event-post clearfix" data-date="<?php echo date( 'm-d-Y', $eventData['start_time'][0] ); ?>">

									<div class="info">

										<span class="date"><?php echo date( 'F j', $eventData['start_time'][0] ); ?></span>
	                                    <span class="divider">|</span>
	                                    <span class="time"><?php echo str_replace( ".", "", $eventData['event_time'][0] ); ?></span>

									</div>

	                                <div class="left">
		                                <?php if( $eventData['web_address'][0] ){ ?>
											<a target="_blank" href="<?php echo hectv_add_http_to_url( trim( $eventData['web_address'][0] ) ); ?>">
										<?php } ?>
										<?php if( has_post_thumbnail( $event->ID ) ){ ?>

											<?php $thumbnail = get_the_post_thumbnail( $event->ID, 'event-thumb' ); ?>

		                                    <?php echo $thumbnail; ?>

	                                    <?php }else{ ?>


	                                    	<?php if ( preg_match( '/<img[^>]+>/is', $event->post_content, $images ) ) { ?>
	                                    	<?php echo preg_replace( '/(width|height)="\d*"\s/', "", $images[0] ); ?>
											<?php } ?>

	                                    <?php } ?>
	                                    <?php if( $eventData['web_address'][0] ){ ?>
											</a>
										<?php } ?>
	                                </div>

	                                <div class="right">
		                                <?php if( $eventData['web_address'][0] ){ ?>
		                                <a target="_blank" href="<?php echo hectv_add_http_to_url( trim( $eventData['web_address'][0] ) ); ?>">
			                            <?php } ?>
		                                    <h4 class="title"><?php echo get_the_title( $event->ID ); ?></h4>
	                                    <?php if( $eventData['web_address'][0] ){ ?>
		                                </a>
		                                <?php } ?>

	                                    <p class="venue"><?php echo $eventData['venue'][0]; ?></p>
	                                    <?php echo wpautop( $event->post_content ); ?>

	                                </div>
	                            </li>

                            <?php endforeach; ?>

                            	<li id="no-results">

                            		<h2>Sorry No Events Found.</h2>

                            	</li>

                        </ul>

                    </div> <!-- End Events List -->

                </div> <!-- End Inner -->

            </div>

        </section> <!-- end page left-->

        <section class="page-right"> <!-- start page right-->
        		
        	<?php hectv_create_tag_cloud("event_type"); ?>
        	
        	<?php hectv_create_submit_event_cta(); ?>

            <?php hectv_create_trending(5); ?>

            <?php hectv_create_tv_schedule(3); ?>

        </section> <!-- end page right-->

    </div> <!--- end inner -->

</main>

<?php get_footer(); ?>
