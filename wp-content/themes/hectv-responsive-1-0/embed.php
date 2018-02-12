<?php
/*
Template Name: Embedder
*/
?>
<?php nocache_headers(); ?>
<?php $video_id   = get_query_var( "video_id" ); ?>
<?php $video_data = get_post_custom( $video_id ); ?>
<?php $thumb      = wp_get_attachment_image_src( $video_data['video_image'][0], 'full' ); ?>
<?php if( empty( $video_data ) && ( get_post_type( $video_id ) != "lb_video" || get_post_type( $video_id ) != "lb_playlist" ) ){ ?>

	<span>Invalid Embed</span>
	<?php die; ?>

<?php } ?>
<?php if( !empty($video_data['vimeo_id'][0]) ){ ?>
				
	<?php $url = 'https://player.vimeo.com/video/'.$video_data['vimeo_id'][0].'?color=0c88dd&title=0&byline=0&portrait=0&rel=0&badge=0&autoplay=true&api=1&player_id=vimeo'; ?>
	<?php header( 'Location: ' . $url ); ?>
	
<?php }else if( !empty($video_data['youtube_id'][0]) ){ ?>
	
	<?php $url = 'https://www.youtube.com/embed/'.$video_data['youtube_id'][0].'/?modestbranding=1&rel=0&autoplay=1&title='; ?>
	<?php header( 'Location: ' . $url ); ?>
	
<?php } ?>

<?php if( empty( $video_data['video_embed'][0] ) ){ ?>

	<div style='background-color:#fff;height:70%;width:100%;padding-top:20%;'><center>This content cannot be embedded.<br/><br/><a href="mailto:info@hectv.org">Contact Us...</a></center></div>

	<?php die; ?>

<?php } ?>

<?php $files = array(); ?>

<?php if( $video_data['media_type'][0] == "sbr" ){ ?>

	<?php $files[]  = "http://hectv.bc.cdn.bitgravity.com/" . $video_data['sbr_file'][0]; ?>

<?php }else if( $video_data['media_type'][0] == "vbr" ){ ?>

	<?php $segment_video = unserialize( $video_data["video_files"][0] ); ?>

	<?php foreach( $segment_video['location'] as $video ){ ?>

		<?php $files[]  = "http://hectv.bc.cdn.bitgravity.com/" . $video; ?>

	<?php } ?>

<?php }else if( $video_data['smil_file'][0] ){ ?>

	<?php $segment_video = unserialize( $video_data["segment_files"][0] ); ?>

	<?php $files[]  = get_bloginfo('template_directory') . "/_/smil/" . $video_data['smil_file'][0]; ?>
	<?php $files[]  = "http://hectv.bc.cdn.bitgravity.com/" . $segment_video['location'][1]; ?>

<?php } ?>
<style type="text/css">
	body{ margin:0; padding: 0; background-color: #000; color: #fff; }
</style>
<!-- include youtube and vimeo -->
<?php $segmentsList  = unserialize( $video_data['segment_child'][0] ); ?>

<?php if( is_array( $segmentsList ) ){ ?>
    <?php foreach($segmentsList as $segment){ ?>
        <?php $status = get_post_status( $segment ); ?>
        <?php if( $status == "publish" || $status == "inherit" ||  is_user_logged_in() ){ ?>
        <?php $segments[] = $segment; ?>
        <?php } ?>
    <?php } ?>
<?php } ?>

<?php  

if(is_array($segments) && !empty($segments[0])) { 
    
    $firstSegmentVimeo = get_post_custom_values( "vimeo_id", $segments[0] )[0];
    $firstSegmentYT = get_post_custom_values( "youtube_id", $segments[0] )[0];
 
} else {
    
    if( is_array($video_data['youtube_id']) && !empty($video_data['youtube_id'][0]) ) {
        
        $firstSegmentYT = $video_data['youtube_id'][0];
        
    }
    
    if( is_array($video_data['vimeo_id']) && !empty($video_data['vimeo_id'][0]) ) {
        
        $firstSegmentVimeo = $video_data['vimeo_id'][0];
        
    }
    
}
?>

<?php if( !empty($firstSegmentVimeo) ) { ?>
    <div id="video-player">
        <iframe src="https://player.vimeo.com/video/<?php echo $firstSegmentVimeo; ?>?color=0c88dd&title=0&byline=0&portrait=0&rel=0&badge=0&autoplay=true&api=1&player_id=vimeo" id="vimeo" class="resize-ratio" width="100%" height="100%" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
    </div>
<?php } elseif( !empty($firstSegmentYT) ) { ?>
    <div id="video-player">
        <iframe width="100%" height="100%" class="resize-ratio" src="https://www.youtube.com/embed/<?php echo $firstSegmentYT; ?>/?modestbranding=1&rel=0&autoplay=1&title=" frameborder="0" allowfullscreen></iframe>
    </div>
<?php } else { ?>
<!-- end include vimeo/yt -->
<div id="video-player" class="resize-ratio"></div>
<script type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/_/js/player/jwplayer.js"></script>
<script type="text/javascript">jwplayer.key="MxVMVlCE58XzB/wuMbVnduQ6Bkov/H9JQnx9mw==";</script>
<script type="text/javascript">

	jwplayer("video-player").setup({
		autostart: false,
		width: '100%',
		height: 720,
		image: '<?php echo $thumb[0]; ?>',
		skin: 'glow',
		sources: [
		<?php foreach( $files as $file ){ ?>
		{ file: "<?php echo $file; ?>" },
	    <?php } ?>
	    ],
	    primary: 'flash',
	    startparam: 'ec_seek',
	    aspectratio: "16:9"
    });

</script>
<?php } ?>