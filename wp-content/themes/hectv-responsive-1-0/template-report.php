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


<?php $playlists = get_posts( array( 'post_type' => 'lb_playlist', 'orderby' => 'parent', 'order' => 'DESC', 'posts_per_page' => -1, 'offset' => 1000 ) ); ?>
<?php foreach( $playlists as $playlist ){ ?>

	<?php $playlist_data = get_post_custom( $playlist->ID ); ?>

	<?php $playlist_keywords = wp_get_post_terms( $playlist->ID, 'keyword' ); ?>
	
	<?php $keyword_list = array(); ?>
	<?php foreach( $playlist_keywords as $playlist_keyword ){ ?>
		<?php $keyword_list[] = $playlist_keyword->name; ?>
	<?php } ?>
	
	<?php $array_to_csv[] = array( $playlist->post_title, $playlist->post_content, implode( ', ', $keyword_list ), get_the_title( $playlist->post_parent ), $playlist_data['internal_id'][0], hectv_formatDuration( $playlist_data['duration'][0] ), $playlist->post_date ); ?>

<?php } ?>


<?php echo convert_to_csv( $array_to_csv, 'report.csv', ',' ); ?>

