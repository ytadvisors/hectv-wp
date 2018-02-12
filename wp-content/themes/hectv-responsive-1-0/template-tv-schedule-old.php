<?php
/*
Template Name: OLD TV
*/
?>
<?php get_header(); ?>

<main class="page clearfix" id="tv-schedule">

	<div class="page-inner clearfix">

    	<section class="page-left">

			<?php if (have_posts()): while (have_posts()) : the_post(); ?>

            <div class="page-description module-wide">

                <div class="inner">

                    <h2><?php the_title(); ?></h2>

                    <?php the_content(); ?>

                </div>

            </div>

            <?php endwhile; ?>

            <?php endif; ?>

            <div class="tv-schedule module-wide">

                <div class="inner">

                    <div class="calendar clearfix">

                        <div class="week">

                            <span class="arrow-left"><</span>

                            <span>This Week</span>

                            <span class="arrow-right">></span>

                        </div>

                        <ul class="dates clearfix">

                            <li>
                                <span class="month">January</span>
                                <span class="date">21st</span>
                                <span class="day">Monday</span>
                            </li>

                            <li>
                                <span class="month">January</span>
                                <span class="date">21st</span>
                                <span class="day">Monday</span>
                            </li>

                            <li>
                                <span class="month">January</span>
                                <span class="date">21st</span>
                                <span class="day">Monday</span>
                            </li>

                            <li>
                                <span class="month">January</span>
                                <span class="date">21st</span>
                                <span class="day">Monday</span>
                            </li>

                            <li>
                                <span class="month">January</span>
                                <span class="date">21st</span>
                                <span class="day">Monday</span>
                            </li>

                            <li>
                                <span class="month">January</span>
                                <span class="date">21st</span>
                                <span class="day">Monday</span>
                            </li>

                            <li>
                                <span class="month">January</span>
                                <span class="date">21st</span>
                                <span class="day">Monday</span>
                            </li>

                        </ul>

                    </div>

                    <div class="btn-wrap">

                        <button class="btn">View Previously Aired</button>

                    </div>

                    <ul>

                        <li class="schedule-post clearfix">

                            <div class="left">

                                <img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-event-post-temp.jpg">

                            </div>

                            <div class="right">
                                <span class="date">Today</span>
                                <span class="divider">|</span>
                                <span class="time">4:00pm</span>

                                <h4 class="series-name">Series Name</h4>

                                <p>Director Stephen Low wanted to create a film that combined his love of the latest in high-fidelity cinematic and the older technology of steam locomo... <a class="see-more" href="#">See More.</a></p>

                            </div>

                        </li>

                        <li class="schedule-post clearfix">

                            <div class="left">

                                <img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-event-post-temp.jpg">

                            </div>

                            <div class="right">

                                <span class="date">Today</span> <span class="divider">|</span><span class="time">4:00pm</span>
                                <h4 class="series-name">Series Name</h4>

                                <p>
                                Director Stephen Low wanted to create a film that combined his love of the latest in high-fidelity cinematic and the older technology of steam locomo... <a class="see-more" href="#">See More.</a>
                                </p>
                            </div>

                        </li>

                        <li class="schedule-post clearfix">

                            <div class="left">

                                <img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-event-post-temp.jpg">

                            </div>

                            <div class="right">

                                <span class="date">Today</span>
                                <span class="divider">|</span>
                                <span class="time">4:00pm</span>
                                <h4 class="series-name">Series Name</h4>

                                <p>
                                Director Stephen Low wanted to create a film that combined his love of the latest in high-fidelity cinematic and the older technology of steam locomo... <a class="see-more" href="#">See More.</a>
                                </p>
                            </div>

                        </li>

                        <li class="schedule-post clearfix">

                            <div class="left">

                                <img src="<?php bloginfo('template_directory'); ?>/_/graphics/ui-event-post-temp.jpg">

                            </div>

                            <div class="right">

                                <span class="date">Today</span>
                                <span class="divider">|</span>
                                <span class="time">4:00pm</span>
                                <h4 class="series-name">Series Name</h4>
                                <p>Director Stephen Low wanted to create a film that combined his love of the latest in high-fidelity cinematic and the older technology of steam locomo... <a class="see-more" href="#">See More.</a></p>

                            </div>

                        </li>

                    </ul>



                 </div> <!-- End Inner -->

            </div>

    	</section> <!--End Page Left -->

    	<section class="page-right">

        	<?php hectv_create_trending(5); ?>

            <div class="module" id="events">

                <div class="inner">

                    <h2>Events</h2>

                    <article>

                        <h3>60th Anniversary Celebration of the McDonnell f-101 Voodoo At The Missouri Avaition Historical Society</h3>

                        <div class="post-info">
                            Posted On: November 12th, 2014 By Christina Chastain
                        </div>

                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>

                    </article>

                    <article>

                        <h3>60th Anniversary Celebration of the McDonnell</h3>

                        <div class="post-info">
                            Posted On: November 12th, 2014 By Christina Chastain
                        </div>

                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna</p>

                    </article>

                </div>

                <div class="flex"></div>

            </div>

    	</section>

	</div>

</main>

<?php get_footer(); ?>
