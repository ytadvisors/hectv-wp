<?php
/*
Template Name: Event Submission
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


			<div class="module-wide">
				
				<div class="inner">
				
					<form id="submit-event-form" enctype="multipart/form-data" method="post" action="/">
						
						<input type="hidden" name="action" value="event-submission">		
						
						<div class="field">
							<input type="name" name="event-name" placeholder="Event Name" class="required">
						</div>
						
						<div class="time-date-fields clearfix">
							
							<div class="field">
								<input type="text" name="time" placeholder="Time" class="required">
								<label><span>ex. 12pm - 6pm</span></label>
							</div>
							
							<div class="field">
								<input type="text" name="date" placeholder="Date" class="required">
								<label><span>ex. November 5</span></label>
							</div>
					
						</div>
						
						<div class="field">
							<input type="text" name="location" placeholder="Location" class="required">
						</div>
						
						<div class="field">
							<input type="text" name="url" placeholder="URL" class="required">
						</div>
						
						<div class="field">
							<textarea placeholder="Description" name="description" form="submit-event-form"></textarea>
						</div>
						
						<div class="btn-wrap upload">
							<input id="event-form-upload" name="event-form-upload" type="file" accept=".doc, .docx, .pdf">
							<label for="event-form-upload"><span class="btn">Upload</span>
							Upload a PDF or .doc file of a press release or other info</label>
						</div>
						
						<div class="btn-wrap">
							<button class="btn" type="submit">Submit</button>
						</div>
						
						
					</form>
					
					<?php if( $_GET['success'] == true ){ ?>
					
					<div class="response">
			    		<div class="message">
				    		<h2>Event Submitted</h2>
				    		<p>Your event is pending HECTV staff approval.</p>
			    		</div>
		    		</div>
					
					<script type="text/javascript">
						
						jQuery('form#submit-event-form').hide();
						
					</script>
		    		
					<?php } ?>
					
					<?php if( $_GET['errors'] == true ){ ?>
					
					<div class="response" style="margin-bottom: 20px;">
			    		<div class="message">
				    		<h2>Something went wrong...</h2>
				    		<p style="margin-bottom: 40px;"><?php echo $_GET['message']; ?></p>
				    		<span class="btn">Try Again.</span>
			    		</div>
		    		</div>
		    		
		    		<script type="text/javascript">
			    		
			    		var $submitForm = jQuery('form#submit-event-form');
						
						$submitForm.hide();
						
						jQuery('span.btn').on('click', function(e){
							
							jQuery('div.response').hide(250);
							jQuery($submitForm).show(250);
							
							
						});
						
						
					</script>
		    		
					<?php } ?>
					
				</div>
								
			</div> <!-- end module -->

        </section> <!-- end page left-->

        <section class="page-right"> <!-- start page right-->
        
			

        	<?php hectv_create_tag_cloud("event_type"); ?>
			
            <?php hectv_create_trending(5); ?>

            <?php hectv_create_tv_schedule(3); ?>

        </section> <!-- end page right-->

    </div> <!--- end inner -->

</main>

<?php get_footer(); ?>
