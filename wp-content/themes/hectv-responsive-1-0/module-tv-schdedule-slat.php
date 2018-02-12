<?php function hectv_create_tv_schedule( $display = 3 ){ ?>
<?php current_time( "m-d-Y", 0 ); ?>
<?php $currentDay = strtotime( current_time( "Y-m-d", 0 ) ); ?>

<?php $start = $currentDay + ( 18 * ( 60 * 60 ) ); ?>
<?php $end   = $currentDay + ( ( 60 * 60 ) * 23 );?>

<?php global $wpdb; ?>
<?php $schedule = $wpdb->get_results( "SELECT * FROM wp_schedule WHERE time BETWEEN $start AND $end AND approved = '1'" ); ?>

<?php foreach( $schedule as $index => $scheduleItem ){ ?>

<?php $x              = $scheduleItem->time; ?>
<?php $scheduleData[] = array( "time" => $scheduleItem->time, "seriesID" => $scheduleItem->seriesID, "episodeID" => $scheduleItem->episodeID ); ?>

<?php } ?>

<div id="schedule" class="module dark clearfix">
	<div class="inner">
		<h2>TV Schedule</h2>
		<div class="schedule-wrapper">
			<h3><?php echo date('l F jS'); ?></h3>
			<ul class="schedule-shows clearfix">

				<?php if( is_array( $scheduleData ) ){ ?>
					<?php foreach( $scheduleData as $index => $item ){ ?>

						<?php $seriesTitleDisable = get_post_meta( $item['episodeID'], 'series_title_disable' ); ?>

						<?php if( $seriesTitleDisable[0] ){ ?>
						<?php $title  = get_the_title( $item['episodeID'] ); ?>
						<?php }else{ ?>
						<?php $title  = get_the_title( $item['seriesID'] ) . ": " . get_the_title( $item['episodeID'] ); ?>
						<?php } ?>

						<?php $title  = str_replace( "Private: ", "", $title ); ?>
						<?php $time   = date( "g:ia", $item['time'] ); ?>
						<?php $link   = get_post_status( $item['episodeID'] ); ?>
						<?php $status = ( $link == "publish" ) ? true : false; ?>

						<?php if( $index == 0 ){ ?>
						<li class="schedule-shows-item clearfix" id="first-scheduled" rel="<?php echo $item['time']; ?>" style="position:relative;">
						<?php }else{ ?>
						<li class="schedule-shows-item clearfix" rel="<?php echo $item['time']; ?>" style="position:relative;margin:3px 0px;" id="<?php echo $item['episodeID']; ?>">
						<?php } ?>

						<?php if( $status ){ ?>
							<a href="<?php echo get_the_permalink( $item['episodeID'] ); ?>">
						<?php } ?>
								<span class="time"><?php echo $time; ?></span>
								<span class="show"><?php echo $title; ?></span>
						<?php if( $status ){ ?>
							</a>
						<?php } ?>
						</li>


					<?php } ?>
				<?php }else{ ?>

					<li class="error">
						<span class="show">The TV Schedule is not available...</span>
					</li>

				<?php } ?>

			</ul>
		</div>
		<div class="btn-wrap flex clearfix">
			<a href="<?php echo site_url('tv-schedule'); ?>" class="btn" style="">View All</a>
		</div>
	</div>
</div>
<?php } ?>