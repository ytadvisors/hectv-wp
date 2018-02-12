<?php
/*
Template Name: User Profile Template
*/
?>

<?php get_header(); ?>

<?php $saved_videos = get_user_meta( $user_ID, 'saved_videos', true ); ?>

<?php if ( !is_array( $saved_videos ) ){ ?>
		
	<?php $saved_videos = array(); ?>
		
<?php } ?>

<main class="page clearfix" id="user-profile">

	<?php if (have_posts()): while (have_posts()) : the_post(); ?>
	
	<div class="page-inner clearfix">
		
		<div class="copy clearfix">

			<h3>Welcome, <?php $current_user = wp_get_current_user(); echo $current_user->user_login; ?></h3>
			
			<?php if( is_array( $saved_videos ) ){ ?>
				
				<span>You have <em class="video-count"><?php echo count( $saved_videos ); ?></em> saved videos</span>
				
			<?php } ?>
			

			<div class="button-wrap">
				<a class="btn" href="/user-settings">Edit Profile</a>
			</div>

		</div>
		
		
		<?php if( is_array( $saved_videos ) ){ ?>
			<div class="videos-wrap">
				
			<?php if( count( $saved_videos ) == 0 ){ ?>
				
<!-- 				<h3>You have no saved videos</h3> -->
								
			<?php }else{ ?>
				
				<h3>Saved Videos</h3>
				
			<?php } ?>	
				
			<?php foreach( $saved_videos as $saved_video ){ ?>
			
				<?php hectv_create_media_slat( 'saved', get_post( $saved_video ) ); ?>
			
			<?php } ?>
			</div>
			
		<?php } ?>
		
		</div>
	</div>
	
	
	<?php endwhile; ?>
	<?php endif; ?>


<?php get_footer(); ?>
