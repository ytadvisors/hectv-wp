<?php

add_action('add_meta_boxes', function() {

	add_meta_box( 'series-related-links', 'Related Links', 'hectv_add_series_page_meta', 'page', 'normal', 'high');

});

function hectv_add_series_page_meta( $post ){


	$related_links = get_post_meta( $post->ID, "related_links", true );

	?>

	<link rel="stylesheet" href="<?php bloginfo('template_directory'); ?>/_/inc/editor-assets/style.css">
	<script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/_/inc/editor-assets/jquery-ui-min.js"></script>
	<script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/_/inc/editor-assets/functions.js"></script>

	<div id="playlist-editor">

		<fieldset>

			<div class="field dynamic large">

				<ul id="related-links">

					<?php if( is_array( $related_links ) ){ ?>

						<?php foreach( $related_links as $related_link ){ ?>

						<li class="link">
							<label class="title-label" style="margin-right:5px;">Link Copy: </label>
							<input type="text" class="text link-title" name="related_links[name][]" value="<?php echo $related_link['name']; ?>">
							<label class="url-label">Address: </label>
							<input type="text" class="text link-url " name="related_links[link][]" value="<?php echo $related_link['link']; ?>">
							<a href="#" class="remove">Remove</a>
							<a href="#" class="add">Add</a>
						</li>

						<?php } ?>

					<?php }else{ ?>

						<li class="link">
							<label class="title-label" style="margin-right:5px;">Link Copy: </label>
							<input type="text" class="text link-title" name="related_links[name][]" value="<?php echo $related_link['name']; ?>">
							<label class="url-label">Address: </label>
							<input type="text" class="text link-url " name="related_links[link][]" value="<?php echo $related_link['link']; ?>">
							<a href="#" class="remove">Remove</a>
							<a href="#" class="add">Add</a>
						</li>

					<?php } ?>

				</ul>

			</div>

		</fieldset>

	</div>

<?php }

	add_action( 'save_post', 'hectv_series_page_save' );

	function hectv_series_page_save(){

		global $post;

		if( $post->post_type == "page" && ( isset( $_POST['save'] ) || isset( $_POST['publish'] ) ) ){

			//

			if( is_array( $_POST['related_links'] ) ){

				$x = 0;
				foreach( $_POST['related_links']['name'] as $index => $related_link ){

					$store_related_links[] = array( "name" => $_POST['related_links']['name'][$x], "link" => $_POST['related_links']['link'][$x] );
					$x++;

				}

			}

			update_post_meta( $post->ID, "related_links", $store_related_links );

		}


	}

?>