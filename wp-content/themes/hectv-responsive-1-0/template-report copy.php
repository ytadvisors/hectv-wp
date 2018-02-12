<?php
/*
Template Name: Reports
*/

function convert_to_csv($input_array, $output_file_name, $delimiter) {
	
    $f = fopen('php://memory', 'w');
    /** loop through array  */
    foreach ($input_array as $line) {
        fputcsv($f, $line, $delimiter);
    }

    fseek($f, 0);

    header('Content-Type: application/csv');
    header('Content-Disposition: attachement; filename="' . $output_file_name . '";');

    fpassthru($f);
    
}

?>

<?php nocache_headers(); ?>

<style type="text/css">
	
	table {
		
		font-family: 'Arial', sans-serif;
		
	}
	
	th {
		
		text-align: left;
		
	}
	
	td {
		
		vertical-align: top;
		overflow: hidden;
		
	}
	
	td:last-of-type {
		
		white-space: nowrap;
		
	}
	
</style>

<div style="position:fixed;left:0px;top:0px;background-color:#fff;width:100%;padding:8px;">
	<table style="width:100%">
		<tr>
			<th style="width:35%;">Playlist Title</th>
			<th style="width:30%;">Description</th>
			<th style="width:15%;">Keywords</th> 
			<th style="width:10%;">Series</th> 
			<th style="width:3%;">Internal ID</th>
			<th>TRT</th>
			<th>Published</th>
		</tr>
	</table>
</div>

<div style="padding-top:100px;">
	<table style="width:100%">	
		<?php $playlists = get_posts( array( 'post_type' => 'lb_playlist', 'orderby' => 'parent', 'order' => 'DESC', 'posts_per_page' => 10 ) ); ?>
		<?php foreach( $playlists as $playlist ){ ?>
		<?php $playlist_data = get_post_custom( $playlist->ID ); ?>
		<?php $playlist_keywords = wp_get_post_terms( $playlist->ID, 'keyword' ); ?>
		<?php foreach( $playlist_keywords as $playlist_keyword ){ ?>
			<?php $keyword_list[] = $playlist_keyword->name; ?>
		<?php } ?>
		<tr>
			<?php $array_to_csv[] = array( $playlist->post_title, $playlist->post_content, implode( ', ', $keyword_list ), get_the_title( $playlist->post_parent ), $playlist_data['internal_id'][0], hectv_formatDuration( $playlist_data['duration'][0] ), $playlist->post_date ); ?>
			<td style="vertical-align:top;width:35%;"><?php echo $playlist->post_title; ?></td>
			<td style="width:30%;vertical-align:top;"><?php echo $playlist->post_content; ?></td>
			<td style="width:15%;vertical-align:top;"><?php echo implode( ', ', $keyword_list ); ?></td>
			<td style="width:10%;"><?php echo get_the_title( $playlist->post_parent ); ?></td> 
			<td style="width:3%;"><?php echo $playlist_data['internal_id'][0]; ?></td>
			<td><?php echo hectv_formatDuration( $playlist_data['duration'][0] ); ?></td>
			<td><?php echo $playlist->post_date; ?></td>
		</tr>
		<?php } ?>
	</table>
	
	<?php convert_to_csv( $array_to_csv ); ?>
	
</div>
