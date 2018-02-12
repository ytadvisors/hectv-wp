<?php
/*
Template Name: Smil Generator
*/
?>
<?php $assetID   = get_query_var( "assetid" ); ?>
<?php nocache_headers(); ?>
<?php if( empty( $assetID ) || !is_numeric( $assetID ) ){ ?>
<?php die; ?>
<?php } ?>
<?php $remotePath = get_post_custom_values( "segment_files", $assetID ); ?>
<?php $video      = unserialize( $remotePath[0] ); ?>
<?php if( !is_array( $video['location'] ) ){ ?>
<?php die; ?>
<?php } ?>
<?php $contents  = "<smil>\n"; ?>
<?php $contents .= "\t<head>\n"; ?>
<?php $contents .= "\t</head>\n"; ?>
<?php $contents .= "\t<body>\n"; ?>
<?php $contents .= "\t\t<switch>\n"; ?>
<?php foreach( $video['location'] as $index => $entry ){ ?>

	<?php $bitrate   = $video['bitrate'][$index] * 1000; ?>
	<?php $location  = basename( $video['location'][$index] ); ?>
	<?php $contents .= "\t\t\t<video src=\"$location\" system-bitrate=\"$bitrate\"/>\n"; ?>

<?php } ?>
<?php $contents .= "\t\t</switch>\n"; ?>
<?php $contents .= "\t</body>\n"; ?>
<?php $contents .= "</smil>\n"; ?>
<?php print $contents; ?>