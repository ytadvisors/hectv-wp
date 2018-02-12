<?php

add_action('add_meta_boxes', function() {

	add_meta_box( 'preferred-recent-episodes', 'Preferred Recent Episodes', 'hectv_add_preferred_recent_episodes_meta', ['page', 'lb_video', 'lb_playlist'] , 'normal', 'high');

});

function hectv_add_preferred_recent_episodes_meta( $post ){


	$preferred_recent_episodes = get_post_meta( $post->ID, "preferred_recent_episodes", true );

	?>

	<script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/_/inc/editor-assets/preferred-recent-episodes-meta-functions.js"></script>
    
    <p>
        <i> The Episode ID is the numeric Post ID which is normally found in the URL of the page or in the URL of the editor.</i>
    </p>

	<fieldset>

        <div class="field dynamic large">

            <ul id="preferred-related-episodes">

                <?php if( is_array( $preferred_recent_episodes ) ){ ?>

                    <?php foreach( $preferred_recent_episodes as $preferred_episode ){ ?>

                    <li class="episode">
                        <label class="title-label" style="margin-right:5px;">Episode ID: </label>
                        <input type="text" class="text link-title" name="preferred_recent_episodes[id][]" value="<?php echo $preferred_episode['id']; ?>">
                        <a href="#" class="js-remove-preferred-episode">Remove</a>
                        <a href="#" class="js-add-preferred-episode">Add</a>
                    </li>

                    <?php } ?>

                <?php }else{ ?>

                    <li class="episode">
                        <label class="title-label" style="margin-right:5px;">Episode ID: </label>
                        <input type="text" class="text link-title" name="preferred_recent_episodes[id][]" value="">
                        <a href="#" class="js-remove-preferred-episode">Remove</a>
                        <a href="#" class="js-add-preferred-episode">Add</a>
                    </li>

                <?php } ?>

            </ul>

        </div>

    </fieldset>

<?php }

	add_action( 'save_post', 'hectv_preferred_recent_episodes_page_save' );

	function hectv_preferred_recent_episodes_page_save(){

		global $post;

		if( ($post->post_type == "page" || $post->post_type == "lb_video" || $post->post_type == "lb_playlist" ) && ( isset( $_POST['save'] ) || isset( $_POST['publish'] ) ) ){

			
			if( is_array( $_POST['preferred_recent_episodes'] ) ){

				$x = 0;
				foreach( $_POST['preferred_recent_episodes']['id'] as $index => $preferred_recent_episode ){

					$store_preferred_recent_episodes[] = array( "id" => $_POST['preferred_recent_episodes']['id'][$x]);
                    $x++;

				}

			}

			update_post_meta( $post->ID, "preferred_recent_episodes", $store_preferred_recent_episodes );

		}


	}

?>